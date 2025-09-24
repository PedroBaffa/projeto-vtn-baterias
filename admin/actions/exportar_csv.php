<?php
// Arquivo: admin/actions/exportar_csv.php (Caminho Corrigido)

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

require_once '../config.php'; // CAMINHO ATUALIZADO

// A consulta base agora inclui a junção com a tabela de imagens e ORDENAÇÃO na concatenação
$base_sql = "
    SELECT 
        p.brand, 
        p.title, 
        p.sku, 
        p.price, 
        p.capacity, 
        p.condicao, 
        p.descricao,
        (SELECT GROUP_CONCAT(pi.image_path ORDER BY pi.ordem ASC SEPARATOR ', ') 
         FROM produto_imagens pi 
         WHERE pi.produto_id = p.id) as images
    FROM 
        produtos p
";

// Lógica para exportar produtos selecionados ou todos
if (!isset($_POST['ids']) || empty($_POST['ids'])) {
    // Exporta TODOS os produtos
    $sql = $base_sql . " ORDER BY p.sku ASC";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    } catch (PDOException $e) {
        die("Erro ao buscar todos os produtos: " . $e->getMessage());
    }
} else {
    // Exporta os produtos selecionados
    $ids_string = $_POST['ids'];
    $ids_array = explode(',', $ids_string);
    $ids_para_exportar = array_filter($ids_array, 'is_numeric');

    if (empty($ids_para_exportar)) {
        die("IDs inválidos.");
    }

    try {
        $placeholders = implode(',', array_fill(0, count($ids_para_exportar), '?'));
        $sql = $base_sql . " WHERE p.id IN ($placeholders) ORDER BY p.sku ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($ids_para_exportar);
    } catch (PDOException $e) {
        die("Erro ao buscar produtos selecionados: " . $e->getMessage());
    }
}

// Define o nome do arquivo e os cabeçalhos para o download
$filename = "produtos_exportados_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Abre o fluxo de saída para escrever o arquivo CSV
$output = fopen('php://output', 'w');

// Escreve o novo cabeçalho, incluindo a coluna "images"
fputcsv($output, ['brand', 'title', 'sku', 'price', 'capacity', 'condicao', 'descricao', 'images']);

// Escreve os dados dos produtos no arquivo
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit();
