<?php
// Define o tipo de conteúdo como XML
header("Content-Type: application/xml; charset=utf-8");

// Inclui a configuração do banco de dados
require_once 'admin/config.php';

// Inicia o XML do sitemap
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// URL da página inicial
echo '<url>';
echo '  <loc>' . BASE_URL . 'index.html</loc>';
echo '  <priority>1.0</priority>';
echo '</url>';

// URL do catálogo
echo '<url>';
echo '  <loc>' . BASE_URL . 'catalogo_produtos.html</loc>';
echo '  <priority>0.9</priority>';
echo '</url>';

try {
    // Busca o SKU de todos os produtos
    $stmt = $conn->query("SELECT sku FROM produtos WHERE in_stock = 1");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Adiciona a URL de cada produto ao sitemap
    foreach ($produtos as $produto) {
        $product_url = BASE_URL . 'produto_detalhes.html?sku=' . htmlspecialchars($produto['sku']);
        echo '<url>';
        echo '  <loc>' . $product_url . '</loc>';
        echo '  <priority>0.8</priority>';
        echo '</url>';
    }

} catch (PDOException $e) {
    // Em caso de erro, não faz nada para não quebrar o XML
}

echo '</urlset>';
?>