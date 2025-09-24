<?php
// Arquivo: api.php (Com busca de perguntas pendentes)

ini_set('display_errors', 0);
error_reporting(0);
header("Content-Type: application/json; charset=UTF-8");

require_once 'admin/config.php';

// [NOVO] CASO 0: DEVOLVER DADOS DO USUÁRIO LOGADO
if (isset($_GET['get_user_data'])) {
    session_start();
    if (isset($_SESSION['usuario_id'])) {
        try {
            $stmt = $conn->prepare("SELECT nome, endereco FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['logado' => true, 'usuario' => $usuario]);
        } catch (PDOException $e) {
            echo json_encode(['logado' => false]);
        }
    } else {
        echo json_encode(['logado' => false]);
    }
    exit();
}

// Zera o preço promocional de promoções que expiraram
try {
    $conn->exec("
        UPDATE produtos p
        LEFT JOIN produto_promocao pp ON p.id = pp.produto_id
        LEFT JOIN promocoes pr ON pp.promocao_id = pr.id
        SET p.promotional_price = NULL
        WHERE (pr.data_fim < NOW() OR pr.id IS NULL) AND p.promotional_price IS NOT NULL
    ");
} catch (PDOException $e) {
    // Logar erro em produção
}

// [NOVO] CASO 4: BUSCAR AS PERGUNTAS DO CLIENTE LOGADO
if (isset($_GET['minhas_perguntas'])) {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(403);
        echo json_encode(["error" => "Usuário não autenticado."]);
        exit();
    }

    $usuario_id = $_SESSION['usuario_id'];

    try {
        $stmt_faq = $conn->prepare("
            SELECT f.id, f.pergunta, f.resposta, f.data_pergunta, f.data_resposta, f.resposta_lida, p.title as produto_titulo, p.sku as produto_sku
            FROM faq_perguntas f
            JOIN usuarios u ON f.email_cliente = u.email
            JOIN produtos p ON f.produto_id = p.id
            WHERE u.id = ?
            ORDER BY f.data_pergunta DESC
        ");
        $stmt_faq->execute([$usuario_id]);
        $perguntas = $stmt_faq->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['perguntas' => $perguntas]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erro ao buscar perguntas."]);
    }
    exit();
}

// CASO 3: A PÁGINA INICIAL PEDE OS PRODUTOS EM PROMOÇÃO PARA O HERO
if (isset($_GET['on_promo'])) {
    try {
        $stmt = $conn->prepare("
            SELECT p.title, p.sku, p.brand, p.price, p.promotional_price,
                   (SELECT image_path FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem ASC LIMIT 1) as image
            FROM produtos p
            WHERE p.promotional_price IS NOT NULL 
              AND p.in_stock = 1
              AND EXISTS (SELECT 1 FROM produto_imagens pi WHERE pi.produto_id = p.id)
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


// CASO 1: DETALHES DO PRODUTO (LÓGICA ATUALIZADA)
if (isset($_GET['sku']) && !empty($_GET['sku'])) {
    try {
        $sql_produto = "
            SELECT 
                p.id, p.brand, p.title, p.sku, p.price, p.promotional_price, p.in_stock, p.capacity, p.condicao, p.descricao,
                (SELECT pr.nome FROM promocoes pr JOIN produto_promocao pp ON pr.id = pp.promocao_id WHERE pp.produto_id = p.id AND pr.is_active = 1 AND NOW() BETWEEN pr.data_inicio AND pr.data_fim LIMIT 1) as promotion_name
            FROM produtos p
            WHERE p.sku = :sku 
            AND EXISTS (SELECT 1 FROM produto_imagens pi WHERE pi.produto_id = p.id)
        ";
        $stmt_produto = $conn->prepare($sql_produto);
        $stmt_produto->bindValue(':sku', $_GET['sku']);
        $stmt_produto->execute();
        $produto = $stmt_produto->fetch(PDO::FETCH_ASSOC);

        if ($produto) {
            // Busca as imagens com ID e path
            // Busca as imagens com ID e path
            $sql_imagens = "SELECT id, image_path FROM produto_imagens WHERE produto_id = :produto_id ORDER BY ordem ASC";
            $stmt_imagens = $conn->prepare($sql_imagens);
            $stmt_imagens->bindValue(':produto_id', $produto['id']);
            $stmt_imagens->execute();
            $imagens = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC); // <- Garanta que está a usar FETCH_ASSOC
            $produto['images'] = $imagens;
            // [ALTERAÇÃO] Busca as perguntas com status 'aprovada' OU 'pendente'
            // [ALTERAÇÃO] Busca as perguntas (com nome do cliente) com status 'aprovada' OU 'pendente'
            $stmt_faq = $conn->prepare("SELECT pergunta, resposta, nome_cliente FROM faq_perguntas WHERE produto_id = :produto_id AND status != 'rejeitada' ORDER BY data_resposta DESC, data_pergunta DESC");
            $stmt_faq->execute([':produto_id' => $produto['id']]);
            $produto['faq'] = $stmt_faq->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($produto);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erro ao procurar produto por SKU."]);
    }
    exit();
}

// CASO 2: CATÁLOGO E PESQUISA
else {
    try {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 12;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $baseSql = "FROM produtos p";
        $whereClause = " WHERE EXISTS (SELECT 1 FROM produto_imagens pi WHERE pi.produto_id = p.id)";
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

        $totalStmt = $conn->prepare("SELECT COUNT(p.id) " . $baseSql . $whereClause);
        $totalStmt->execute($params);
        $totalProducts = (int) $totalStmt->fetchColumn();

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

        $productsSql = "SELECT 
                            p.brand, p.title, p.sku, p.price, p.promotional_price, p.in_stock, p.capacity, p.condicao,
                            (SELECT image_path FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem ASC LIMIT 1) as image
                        " . $baseSql . $whereClause . $orderBy . " LIMIT :limit OFFSET :offset";

        $productsStmt = $conn->prepare($productsSql);
        foreach ($params as $key => $value) {
            $productsStmt->bindValue($key, $value);
        }
        $productsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $productsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $productsStmt->execute();
        $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

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

$conn = null;
