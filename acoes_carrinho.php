<?php
/**
 * @file
 * Gerencia todas as ações relacionadas ao carrinho de compras do usuário,
 * como adicionar, remover, atualizar e obter itens.
 * Responde em formato JSON.
 */

// Inicia a sessão para acessar as variáveis de sessão do usuário.
session_start();

// Define o cabeçalho da resposta como JSON com codificação UTF-8.
header('Content-Type: application/json; charset=utf-8');

// Inclui o arquivo de configuração para conexão com o banco de dados.
require_once 'admin/config.php';

//-- VERIFICAÇÃO DE AUTENTICAÇÃO --//

// Garante que apenas usuários logados possam interagir com o carrinho.
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403); // HTTP 403: Proibido
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit();
}

//-- INICIALIZAÇÃO DE VARIÁVEIS --//

// Obtém o ID do usuário logado a partir da sessão.
$usuario_id = $_SESSION['usuario_id'];

// Decodifica o corpo da requisição JSON para um array associativo.
$data = json_decode(file_get_contents('php://input'), true);

// Determina a ação solicitada, buscando em dados POST (JSON) ou GET.
$acao = $data['acao'] ?? $_GET['acao'] ?? '';

//-- ROTAS DE AÇÕES DO CARRINHO --//

/**
 * AÇÃO: Obter Itens
 * Retorna todos os itens presentes no carrinho do usuário logado,
 * incluindo informações do produto como preço normal e promocional.
 */
if ($acao == 'obter') {
    $stmt = $conn->prepare(
        "SELECT
            p.sku, p.title, p.price, p.promotional_price,
            ci.quantidade,
            (SELECT image_path FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem ASC LIMIT 1) as image
        FROM carrinho_itens ci
        JOIN produtos p ON ci.produto_sku = p.sku
        WHERE ci.usuario_id = :usuario_id"
    );
    $stmt->execute([':usuario_id' => $usuario_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['sucesso' => true, 'carrinho' => $itens]);
    exit();
}

/**
 * AÇÃO: Adicionar Item
 * Adiciona um novo produto ao carrinho ou incrementa a quantidade
 * se o produto já existir no carrinho do usuário.
 */
if ($acao == 'adicionar' && isset($data['sku'], $data['quantity'])) {
    $sku = $data['sku'];
    $quantidade = (int) $data['quantity'];

    // Verifica se o item já existe no carrinho.
    $stmt = $conn->prepare("SELECT id, quantidade FROM carrinho_itens WHERE usuario_id = :uid AND produto_sku = :sku");
    $stmt->execute([':uid' => $usuario_id, ':sku' => $sku]);
    $item_existente = $stmt->fetch();

    if ($item_existente) {
        // Se existir, atualiza a quantidade somando a nova.
        $nova_quantidade = $item_existente['quantidade'] + $quantidade;
        $stmt_update = $conn->prepare("UPDATE carrinho_itens SET quantidade = :qtd, adicionado_em = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt_update->execute([':qtd' => $nova_quantidade, ':id' => $item_existente['id']]);
    } else {
        // Se não existir, insere o novo item.
        $stmt_insert = $conn->prepare("INSERT INTO carrinho_itens (usuario_id, produto_sku, quantidade) VALUES (:uid, :sku, :qtd)");
        $stmt_insert->execute([':uid' => $usuario_id, ':sku' => $sku, ':qtd' => $quantidade]);
    }

    echo json_encode(['sucesso' => true, 'mensagem' => 'Item adicionado ao carrinho.']);
    exit();
}

/**
 * AÇÃO: Atualizar Quantidade
 * Altera a quantidade de um item específico no carrinho.
 * A quantidade deve ser maior que zero.
 */
if ($acao == 'atualizar_quantidade' && isset($data['sku'], $data['quantity'])) {
    $sku = $data['sku'];
    $quantidade = (int) $data['quantity'];

    if ($quantidade > 0) {
        $stmt = $conn->prepare("UPDATE carrinho_itens SET quantidade = :qtd, adicionado_em = CURRENT_TIMESTAMP WHERE usuario_id = :uid AND produto_sku = :sku");
        $stmt->execute([':qtd' => $quantidade, ':uid' => $usuario_id, ':sku' => $sku]);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Quantidade atualizada.']);
    }
    // Se a quantidade for 0 ou menor, a operação é simplesmente ignorada.
    // Para remover, o cliente deve chamar a ação 'remover'.
    exit();
}

/**
 * AÇÃO: Remover Item
 * Exclui completamente um item do carrinho do usuário,
 * independentemente da sua quantidade.
 */
if ($acao == 'remover' && isset($data['sku'])) {
    $sku = $data['sku'];
    $stmt = $conn->prepare("DELETE FROM carrinho_itens WHERE usuario_id = :uid AND produto_sku = :sku");
    $stmt->execute([':uid' => $usuario_id, ':sku' => $sku]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Item removido do carrinho.']);
    exit();
}

//-- RESPOSTA PADRÃO DE ERRO --//

// Se nenhuma ação válida for correspondida, retorna um erro.
http_response_code(400); // HTTP 400: Requisição Inválida
echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida ou dados insuficientes.']);