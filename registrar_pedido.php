<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'admin/config.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado. Faça login para continuar.']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['itens']) || !isset($data['valor_total'])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados do pedido incompletos.']);
    exit();
}

$conn->beginTransaction();

try {
    // 1. Inserir na tabela `pedidos`
    $stmt_pedido = $conn->prepare(
        "INSERT INTO pedidos (usuario_id, valor_total, frete_tipo, frete_valor, status) 
         VALUES (:usuario_id, :valor_total, :frete_tipo, :frete_valor, 'chamou')"
    );
    $stmt_pedido->execute([
        ':usuario_id' => $usuario_id,
        ':valor_total' => $data['valor_total'],
        ':frete_tipo' => $data['frete_info']['tipo'] ?? null,
        ':frete_valor' => isset($data['frete_info']['valor']) ? str_replace(',', '.', $data['frete_info']['valor']) : 0.00
    ]);
    $pedido_id = $conn->lastInsertId();

    // 2. Inserir cada item na tabela `pedido_itens` (COM A CORREÇÃO)
    $stmt_item = $conn->prepare(
        "INSERT INTO pedido_itens (pedido_id, produto_sku, quantidade, preco_unitario, preco_promocional_unitario) 
         VALUES (:pedido_id, :sku, :qtd, :preco, :preco_promo)"
    );

    foreach ($data['itens'] as $item) {
        $stmt_item->execute([
            ':pedido_id' => $pedido_id,
            ':sku' => $item['sku'],
            ':qtd' => $item['quantity'],
            // Salva o preço que o cliente pagou (pode ser o normal ou com desconto de atacado)
            ':preco' => $item['unit_price'],
            // Salva o preço promocional original, se houver
            ':preco_promo' => $item['promotional_price']
        ]);
    }

    $conn->commit();

    // Limpa o carrinho do usuário após o pedido ser registrado com sucesso
    $stmt_limpa_carrinho = $conn->prepare("DELETE FROM carrinho_itens WHERE usuario_id = :usuario_id");
    $stmt_limpa_carrinho->execute([':usuario_id' => $usuario_id]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Pedido registrado com sucesso!', 'pedido_id' => $pedido_id]);
} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    // Para depuração, podemos registrar o erro real
    // error_log("Erro ao registrar pedido: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar o pedido no banco de dados.']);
}
