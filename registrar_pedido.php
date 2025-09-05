<?php

/**
 * @file
 * Endpoint para processar e registrar um novo pedido no sistema.
 *
 * Este script recebe os dados de um carrinho de compras via JSON, valida a sessão do usuário,
 * e insere as informações do pedido e seus itens no banco de dados dentro de uma transação.
 * Após o sucesso, limpa o carrinho do usuário e retorna uma resposta JSON.
 *
 * @method POST
 * @requires Autenticação de usuário via sessão (usuario_id).
 * @param JSON {
 * "itens": [
 * {
 * "sku": "SKU_PRODUTO_1",
 * "quantity": 2,
 * "unit_price": "99.90",
 * "promotional_price": "89.90"
 * }
 * ],
 * "valor_total": "179.80",
 * "frete_info": {
 * "tipo": "SEDEX",
 * "valor": "15.50"
 * }
 * }
 * @return JSON Resposta de sucesso ou erro.
 */

// Inicia a sessão para acessar as variáveis de sessão, como o ID do usuário.
session_start();

// Define o cabeçalho da resposta como JSON, garantindo que o cliente interprete os dados corretamente.
header('Content-Type: application/json; charset=utf-8');

// Inclui o arquivo de configuração.
require_once 'admin/config.php';

/**
 * Bloco de Segurança: Verifica se o usuário está logado.
 * Se 'usuario_id' não estiver na sessão, significa que o usuário não está autenticado.
 */
if (!isset($_SESSION['usuario_id'])) {
    // Define o código de status HTTP como 403 (Forbidden), indicando acesso negado.
    http_response_code(403);
    // Retorna uma mensagem de erro em formato JSON e encerra o script.
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado. Faça login para continuar.']);
    exit();
}

// Armazena o ID do usuário logado a partir da sessão para uso posterior.
$usuario_id = $_SESSION['usuario_id'];

// Obtém os dados enviados no corpo da requisição (geralmente via POST/PUT) e decodifica o JSON para um array associativo.
$data = json_decode(file_get_contents('php://input'), true);

/**
 * Validação de Entrada: Verifica se os dados essenciais do pedido foram fornecidos.
 * É crucial garantir que o array de 'itens' e o 'valor_total' existam.
 */
if (empty($data['itens']) || !isset($data['valor_total'])) {
    // Define o código de status HTTP como 400 (Bad Request), indicando uma requisição malformada.
    http_response_code(400);
    // Retorna uma mensagem de erro e encerra o script.
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados do pedido incompletos.']);
    exit();
}

// Inicia uma transação no banco de dados. Isso garante a atomicidade da operação:
// ou todas as queries são executadas com sucesso, ou nenhuma é.
$conn->beginTransaction();

try {
    // --- Etapa 1: Inserir os dados principais na tabela `pedidos` ---

    // Prepara a query SQL para evitar injeção de SQL, usando placeholders.
    $stmt_pedido = $conn->prepare(
        "INSERT INTO pedidos (usuario_id, valor_total, frete_tipo, frete_valor, status) 
         VALUES (:usuario_id, :valor_total, :frete_tipo, :frete_valor, 'chamou')"
    );

    // Executa a query, substituindo os placeholders pelos valores recebidos.
    $stmt_pedido->execute([
        ':usuario_id' => $usuario_id,
        ':valor_total' => $data['valor_total'],
        // Usa o operador de coalescência nula (??) para definir um valor padrão caso 'frete_info' não exista.
        ':frete_tipo' => $data['frete_info']['tipo'] ?? null,
        // Garante que o valor do frete seja um número de ponto flutuante, substituindo vírgula por ponto.
        ':frete_valor' => isset($data['frete_info']['valor']) ? str_replace(',', '.', $data['frete_info']['valor']) : 0.00
    ]);

    // Recupera o ID do pedido que acabou de ser inserido. Este ID será usado como chave estrangeira na tabela `pedido_itens`.
    $pedido_id = $conn->lastInsertId();

    // --- Etapa 2: Inserir cada item do pedido na tabela `pedido_itens` ---

    // Prepara a query para inserir os itens. A mesma declaração será reutilizada para cada item no loop.
    $stmt_item = $conn->prepare(
        "INSERT INTO pedido_itens (pedido_id, produto_sku, quantidade, preco_unitario, preco_promocional_unitario) 
         VALUES (:pedido_id, :sku, :qtd, :preco, :preco_promo)"
    );

    // Itera sobre cada item do pedido recebido no JSON.
    foreach ($data['itens'] as $item) {
        $stmt_item->execute([
            ':pedido_id' => $pedido_id, // ID do pedido mestre.
            ':sku' => $item['sku'],
            ':qtd' => $item['quantity'],
            ':preco' => $item['unit_price'], // O preço efetivamente pago pelo cliente.
            ':preco_promo' => $item['promotional_price'] // O preço promocional original do produto, se houver.
        ]);
    }

    // Se todas as queries foram executadas sem erro, confirma a transação, salvando as alterações permanentemente no banco.
    $conn->commit();

    // --- Etapa 3: Limpar o carrinho do usuário ---

    // Após o pedido ser confirmado, o carrinho correspondente é esvaziado.
    $stmt_limpa_carrinho = $conn->prepare("DELETE FROM carrinho_itens WHERE usuario_id = :usuario_id");
    $stmt_limpa_carrinho->execute([':usuario_id' => $usuario_id]);

    // --- Etapa 4: Retornar resposta de sucesso ---

    // Envia uma resposta JSON confirmando que o pedido foi registrado com sucesso e retorna o ID do novo pedido.
    echo json_encode(['sucesso' => true, 'mensagem' => 'Pedido registrado com sucesso!', 'pedido_id' => $pedido_id]);
} catch (PDOException $e) {
    // Em caso de qualquer erro em uma das queries dentro do bloco 'try', a transação é revertida.
    $conn->rollBack();

    // Define o código de status HTTP como 500 (Internal Server Error).
    http_response_code(500);

    // Em ambiente de desenvolvimento, é útil registrar o erro real para depuração.
    // error_log("Erro ao registrar pedido: " . $e->getMessage());

    // Retorna uma mensagem de erro genérica para o cliente, por segurança.
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar o pedido no banco de dados.']);
}
