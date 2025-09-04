<?php

/**
 * @file
 * Gera e exporta um arquivo CSV com os dados dos produtos.
 * Pode exportar todos os produtos ou apenas uma seleção específica de IDs.
 * Este script não exibe HTML, ele força o download de um arquivo no navegador.
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam exportar dados.
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

// Inclui a conexão com o banco de dados.
require_once 'config.php';

// --- LÓGICA DE SELEÇÃO DE DADOS ---

// Verifica se uma lista de IDs foi enviada via POST a partir do dashboard.
if (!isset($_POST['ids']) || empty($_POST['ids'])) {
    // Se nenhum ID foi enviado, a query buscará TODOS os produtos.
    try {
        $stmt = $conn->prepare("SELECT brand, title, sku, price, capacity, condicao, descricao FROM produtos ORDER BY sku ASC");
        $stmt->execute();
    } catch (PDOException $e) {
        die("Erro ao buscar todos os produtos: " . $e->getMessage());
    }
} else {
    // Se IDs foram enviados, a query buscará apenas os produtos selecionados.
    $ids_string = $_POST['ids'];
    $ids_array = explode(',', $ids_string);
    // Filtra o array para garantir que contenha apenas valores numéricos, por segurança.
    $ids_para_exportar = array_filter($ids_array, 'is_numeric');

    if (empty($ids_para_exportar)) {
        die("IDs inválidos.");
    }

    try {
        // Cria uma string de placeholders (?) para cada ID, prevenindo SQL Injection.
        $placeholders = implode(',', array_fill(0, count($ids_para_exportar), '?'));
        // Prepara a query para buscar produtos cujos IDs estão na lista fornecida.
        $stmt = $conn->prepare("SELECT brand, title, sku, price, capacity, condicao, descricao FROM produtos WHERE id IN ($placeholders) ORDER BY sku ASC");
        $stmt->execute($ids_para_exportar);
    } catch (PDOException $e) {
        die("Erro ao buscar produtos selecionados: " . $e->getMessage());
    }
}

// --- GERAÇÃO E OUTPUT DO ARQUIVO CSV ---

// Define o nome do arquivo que será baixado, incluindo a data atual.
$filename = "produtos_exportados_" . date('Y-m-d') . ".csv";

// 1. Define os cabeçalhos HTTP. Isso informa ao navegador que a resposta é um arquivo CSV
// e que ele deve ser baixado (attachment) em vez de exibido na tela.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 2. Abre um "ponteiro" para o fluxo de saída do PHP (php://output),
// o que permite escrever diretamente na resposta HTTP que será enviada ao navegador.
$output = fopen('php://output', 'w');

// 3. Escreve a primeira linha do CSV, que é o cabeçalho das colunas.
fputcsv($output, ['brand', 'title', 'sku', 'price', 'capacity', 'condicao', 'descricao']);

// 4. Busca todos os resultados da query.
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Itera sobre cada produto e escreve uma linha no arquivo CSV.
foreach ($produtos as $produto) {
    fputcsv($output, $produto);
}

// 5. Fecha o fluxo de escrita.
fclose($output);
// Finaliza a execução do script.
exit();
