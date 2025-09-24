<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// A lógica PHP de paginação, busca e ordenação não muda.
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search_term = $_GET['search'] ?? '';
$filter_sort = $_GET['sort'] ?? 'modificado_desc';

$baseSql = "FROM produtos p";
$whereClause = " WHERE 1=1";
$params = [];

if (!empty($search_term)) {
    $whereClause .= " AND (p.title LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}

$total_products_stmt = $conn->prepare("SELECT COUNT(p.id) " . $baseSql . $whereClause);
$total_products_stmt->execute($params);
$total_products = $total_products_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

$orderBy = " ORDER BY p.data_modificacao DESC, p.id DESC";
switch ($filter_sort) {
    case 'title_asc':
        $orderBy = " ORDER BY p.title ASC";
        break;
    case 'sku_asc':
        $orderBy = " ORDER BY p.sku ASC";
        break;
    case 'sku_desc':
        $orderBy = " ORDER BY p.sku DESC";
        break;
}

$sql = "SELECT p.id, p.title, p.sku " . $baseSql . $whereClause . $orderBy . " LIMIT :limit OFFSET :offset";
$stmt_produtos = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt_produtos->bindValue($key, $value);
}
$stmt_produtos->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_produtos->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_produtos->execute();
$produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);

try {
    $stmt_grupos = $conn->query("SELECT g.nome, i.image_path FROM galeria_grupos g JOIN galeria_imagens i ON g.id = i.grupo_id ORDER BY g.nome ASC, i.ordem ASC");
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
    <title>Galeria de Imagens por Produto - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <link rel="stylesheet" href="../assets/css/galeria_admin.css">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <?php require_once 'templates/sidebar.php'; ?>
        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex justify-between items-center p-4 bg-white border-b sticky top-0 z-20">
                <h1 class="text-xl font-semibold text-gray-700">Galeria de Imagens</h1>
                <div class="flex items-center"><span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span><a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded-lg text-sm">Sair</a></div>
            </header>
            <main class="flex-1 p-4 md:p-6">
                <div class="mb-4 bg-white p-3 rounded-lg shadow-md">
                    <form action="galeria.php" method="GET" class="flex flex-wrap items-end gap-3">
                        <div class="relative flex-grow min-w-[250px]">
                            <label for="search-input" class="block text-xs font-medium text-gray-600">Pesquisar</label>
                            <input type="text" id="search-input" name="search" placeholder="Título ou SKU..." value="<?php echo htmlspecialchars($search_term); ?>" class="mt-1 w-full pl-8 pr-4 py-1.5 border rounded-md text-sm">
                            <div class="absolute inset-y-0 left-0 pl-2.5 pt-5 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                        </div>
                        <div class="flex-grow min-w-[150px]">
                            <label for="sort-filter" class="block text-xs font-medium text-gray-600">Ordenar por</label>
                            <select id="sort-filter" name="sort" class="mt-1 w-full border rounded-md py-1.5 px-2 text-sm">
                                <option value="modificado_desc" <?php if ($filter_sort == 'modificado_desc') echo 'selected'; ?>>Mais Recentes</option>
                                <option value="title_asc" <?php if ($filter_sort == 'title_asc') echo 'selected'; ?>>Título (A-Z)</option>
                                <option value="sku_asc" <?php if ($filter_sort == 'sku_asc') echo 'selected'; ?>>Menor SKU</option>
                                <option value="sku_desc" <?php if ($filter_sort == 'sku_desc') echo 'selected'; ?>>Maior SKU</option>
                            </select>
                        </div>
                        <div class="flex-grow min-w-[120px]">
                            <label for="limit-filter" class="block text-xs font-medium text-gray-600">Itens/Página</label>
                            <select id="limit-filter" name="limit" class="mt-1 w-full border rounded-md py-1.5 px-2 text-sm">
                                <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                                <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md text-sm flex-grow md:flex-grow-0">Aplicar</button>
                    </form>
                </div>

                <div id="product-gallery-container" class="space-y-3">
                    <?php if (empty($produtos)): ?>
                        <div class="text-center py-10 bg-white rounded-lg shadow-md">
                            <p>Nenhum produto encontrado.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($produtos as $produto): ?>
                            <div class="bg-white rounded-lg shadow-md product-gallery-card" data-product-id="<?php echo $produto['id']; ?>" data-sku="<?php echo htmlspecialchars($produto['sku']); ?>">
                                <div class="p-3">
                                    <h3 class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($produto['title']); ?></h3>
                                    <p class="text-xs text-gray-500">SKU: <?php echo htmlspecialchars($produto['sku']); ?></p>
                                </div>
                                <div class="gallery-content p-3 border-t border-gray-200 min-h-[120px]">
                                    <div class="text-center text-gray-400 text-xs py-10">Rolando para carregar...</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex flex-wrap justify-center items-center gap-2">
                        <?php for ($i = 1; $i <= $total_pages; $i++):
                            $queryParams = http_build_query(array_merge($_GET, ['page' => $i])); ?>
                            <a href="?<?php echo $queryParams; ?>" class="px-3 py-1.5 rounded-md border text-sm <?php echo $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-100'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <div id="gallery-modal"></div>
    <script src="../assets/js/galeria.js"></script>
</body>

</html>