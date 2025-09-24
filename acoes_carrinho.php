<?php
// Arquivo: acoes_carrinho.php (Com busca de dados do usuário)

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'admin/config.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);
$acao = $data['acao'] ?? $_GET['acao'] ?? '';

// --- AÇÃO: OBTER ITENS DO CARRINHO (AGORA COM DADOS DO USUÁRIO) ---
if ($acao == 'obter') {
    // 1. Busca os dados do usuário
    $stmt_usuario = $conn->prepare("SELECT nome, sobrenome, email, telefone, endereco FROM usuarios WHERE id = :usuario_id");
    $stmt_usuario->execute([':usuario_id' => $usuario_id]);
    $usuario_info = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

    // 2. Busca os itens do carrinho
    $stmt_itens = $conn->prepare(
        "SELECT 
            p.sku, p.title, p.price, p.promotional_price,
            ci.quantidade, 
            (SELECT image_path FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem ASC LIMIT 1) as image
        FROM carrinho_itens ci
        JOIN produtos p ON ci.produto_sku = p.sku
        WHERE ci.usuario_id = :usuario_id"
    );
    $stmt_itens->execute([':usuario_id' => $usuario_id]);
    $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

    // 3. Combina tudo numa única resposta JSON
    echo json_encode([
        'sucesso' => true,
        'carrinho' => $itens,
        'usuario' => $usuario_info
    ]);
    exit();
}

// --- AÇÃO: ADICIONAR ITEM AO CARRINHO ---
if ($acao == 'adicionar' && isset($data['sku'], $data['quantity'])) {
    $sku = $data['sku'];
    $quantidade = (int) $data['quantity'];

    $stmt = $conn->prepare("SELECT id, quantidade FROM carrinho_itens WHERE usuario_id = :uid AND produto_sku = :sku");
    $stmt->execute([':uid' => $usuario_id, ':sku' => $sku]);
    $item_existente = $stmt->fetch();

    if ($item_existente) {
        $nova_quantidade = $item_existente['quantidade'] + $quantidade;
        $stmt_update = $conn->prepare("UPDATE carrinho_itens SET quantidade = :qtd, adicionado_em = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt_update->execute([':qtd' => $nova_quantidade, ':id' => $item_existente['id']]);
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO carrinho_itens (usuario_id, produto_sku, quantidade) VALUES (:uid, :sku, :qtd)");
        $stmt_insert->execute([':uid' => $usuario_id, ':sku' => $sku, ':qtd' => $quantidade]);
    }
    echo json_encode(['sucesso' => true, 'mensagem' => 'Item adicionado ao carrinho.']);
    exit();
}

// --- AÇÃO: ATUALIZAR QUANTIDADE DE UM ITEM ---
if ($acao == 'atualizar_quantidade' && isset($data['sku'], $data['quantity'])) {
    $sku = $data['sku'];
    $quantidade = (int) $data['quantity'];

    if ($quantidade > 0) {
        $stmt = $conn->prepare("UPDATE carrinho_itens SET quantidade = :qtd, adicionado_em = CURRENT_TIMESTAMP WHERE usuario_id = :uid AND produto_sku = :sku");
        $stmt->execute([':qtd' => $quantidade, ':uid' => $usuario_id, ':sku' => $sku]);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Quantidade atualizada.']);
    }
    exit();
}

// --- AÇÃO: REMOVER ITEM DO CARRINHO ---
if ($acao == 'remover' && isset($data['sku'])) {
    $sku = $data['sku'];
    $stmt = $conn->prepare("DELETE FROM carrinho_itens WHERE usuario_id = :uid AND produto_sku = :sku");
    $stmt->execute([':uid' => $usuario_id, ':sku' => $sku]);
    echo json_encode(['sucesso' => true, 'mensagem' => 'Item removido do carrinho.']);
    exit();
}

// Resposta padrão se nenhuma ação for correspondida
http_response_code(400);
echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida ou dados insuficientes.']);
