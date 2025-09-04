<?php

/**
 * @file
 * api.php
 * Ponto de entrada central da API para o front-end do site.
 * Fornece dados de produtos em formato JSON com base nos parâmetros recebidos via GET.
 * Não gera HTML.
 */

// Garante uma saída JSON limpa, desativando a exibição de erros do PHP.
// Em um ambiente de produção, os erros devem ser registrados em logs, não exibidos.
ini_set('display_errors', 0);
error_reporting(0);
// Define o cabeçalho da resposta para indicar que o conteúdo é JSON com codificação UTF-8.
header("Content-Type: application/json; charset=UTF-8");

// Inclui a conexão com o banco de dados.
require_once 'admin/config.php';

// --- TAREFA DE MANUTENÇÃO: LIMPAR PROMOÇÕES EXPIRADAS ---
// Esta query é executada toda vez que a API é chamada, garantindo que os preços promocionais
// de promoções que já terminaram sejam removidos.
try {
    $conn->exec("
        UPDATE produtos p
        LEFT JOIN produto_promocao pp ON p.id = pp.produto_id
        LEFT JOIN promocoes pr ON pp.promocao_id = pr.id
        SET p.promotional_price = NULL
        WHERE (pr.data_fim < NOW() OR pr.id IS NULL) AND p.promotional_price IS NOT NULL
    ");
} catch (PDOException $e) {
    // Em um ambiente de produção, o ideal é registrar este erro em um arquivo de log.
    // Ex: error_log("Erro ao limpar promoções: " . $e->getMessage());
}

// --- ROTEAMENTO DE REQUISIÇÕES DA API ---

/**
 * ROTA 1: HERO DA PÁGINA INICIAL
 * Se o parâmetro 'on_promo' for recebido, a API retorna uma lista
 * de até 10 produtos aleatórios que estão em promoção e em estoque.
 */
if (isset($_GET['on_promo'])) {
    try {
        $stmt = $conn->prepare("
            SELECT p.title, p.sku, p.brand, p.price, p.promotional_price,
                   (SELECT image_path FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem ASC LIMIT 1) as image
            FROM produtos p
            WHERE p.promotional_price IS NOT NULL 
              AND p.in_stock = 1
            ORDER BY RAND()
            LIMIT 10
        ");
        $stmt->execute();
        $promo_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['promo_products' => $promo_products]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erro ao buscar produtos em promoção."]);
    }
    exit();
}

/**
 * ROTA 2: DETALHES DE UM PRODUTO ESPECÍFICO
 * Se um 'sku' for fornecido, a API retorna todos os detalhes daquele produto.
 */
if (isset($_GET['sku']) && !empty($_GET['sku'])) {
    try {
        // 1. Busca os dados principais do produto.
        $sql_produto = "SELECT id, brand, title, sku, price, promotional_price, in_stock, capacity, condicao, descricao FROM produtos WHERE sku = :sku";
        $stmt_produto = $conn->prepare($sql_produto);
        $stmt_produto->bindValue(':sku', $_GET['sku']);
        $stmt_produto->execute();
        $produto = $stmt_produto->fetch(PDO::FETCH_ASSOC);

        // 2. Se o produto for encontrado, busca todas as suas imagens associadas.
        if ($produto) {
            $sql_imagens = "SELECT image_path FROM produto_imagens WHERE produto_id = :produto_id ORDER BY ordem ASC";
            $stmt_imagens = $conn->prepare($sql_imagens);
            $stmt_imagens->bindValue(':produto_id', $produto['id']);
            $stmt_imagens->execute();
            $imagens = $stmt_imagens->fetchAll(PDO::FETCH_COLUMN, 0);
            $produto['images'] = $imagens; // Adiciona o array de imagens ao objeto do produto.
        }
        echo json_encode($produto);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erro ao procurar produto por SKU."]);
    }
    exit();
}

/**
 * ROTA 3: CATÁLOGO GERAL E PESQUISA (ROTA PADRÃO)
 * Se nenhuma outra condição for atendida, a API retorna uma lista paginada
 * de produtos, aplicando os filtros de busca, marca e ordenação recebidos.
 */
else {
    try {
        // Parâmetros de paginação e filtros
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 12;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Construção dinâmica e segura da query
        $baseSql = "FROM produtos p";
        $whereClause = " WHERE 1";
        $params = [];

        if (isset($_GET['brand']) && $_GET['brand'] !== 'all') {
            $whereClause .= " AND p.brand = :brand";
            $params[':brand'] = $_GET['brand'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $searchTerm = '%' . $_GET['search'] . '%';
            $whereClause .= " AND (p.title LIKE :search OR p.sku LIKE :search OR p.brand LIKE :search)";
            $params[':search'] = $searchTerm;
        }

        // Conta o total de produtos que correspondem aos filtros (para a paginação).
        $totalStmt = $conn->prepare("SELECT COUNT(p.id) " . $baseSql . $whereClause);
        $totalStmt->execute($params);
        $totalProducts = (int) $totalStmt->fetchColumn();

        // Define a ordenação
        $sort = $_GET['sort'] ?? 'title_asc';
        if (isset($_GET['random']) && $_GET['random'] == 'true') {
            $sort = 'random';
        }
        $orderBy = " ORDER BY p.title ASC";
        switch ($sort) {
            case 'price_desc':
                $orderBy = " ORDER BY p.price DESC";
                break;
            case 'price_asc':
                $orderBy = " ORDER BY p.price ASC";
                break;
            case 'capacity_desc':
                $orderBy = " ORDER BY p.capacity DESC, p.title ASC";
                break;
            case 'random':
                $orderBy = " ORDER BY RAND()";
                break;
        }

        // Monta a query final para buscar os produtos da página atual.
        $productsSql = "SELECT 
                            p.brand, p.title, p.sku, p.price, p.promotional_price, p.in_stock, p.capacity, p.condicao,
                            (SELECT image_path FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem ASC LIMIT 1) as image
                        " . $baseSql . $whereClause . $orderBy . " LIMIT :limit OFFSET :offset";

        $productsStmt = $conn->prepare($productsSql);
        // Associa os parâmetros de forma segura.
        foreach ($params as $key => $value) {
            $productsStmt->bindValue($key, $value);
        }
        $productsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $productsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $productsStmt->execute();
        $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Monta a resposta final em JSON, incluindo dados de paginação.
        $response = [
            'total' => $totalProducts,
            'page' => $page,
            'limit' => $limit,
            'products' => $products
        ];
        echo json_encode($response);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Ocorreu um erro ao consultar o catálogo.", "db_error" => $e->getMessage()]);
    }
}

// Fecha a conexão com o banco de dados.
$conn = null;
