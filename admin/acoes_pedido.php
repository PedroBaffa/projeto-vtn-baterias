<?php

/**
 * @file acoes_pedido.php
 * Controlador para gerenciar ações relacionadas a um pedido específico.
 *
 * Este script não gera HTML. Ele processa dados recebidos via POST ou GET
 * para atualizar status, adicionar ou remover itens de um pedido, e então
 * redireciona o usuário de volta para a página de detalhes com uma mensagem.
 */

// Inicia a sessão para garantir que apenas administradores autenticados possam executar estas ações.
session_start();
if (!isset($_SESSION['user_id'])) {
    // Se não houver sessão ativa, interrompe a execução.
    die("Acesso negado.");
}
// Inclui o arquivo de configuração para conexão com o banco de dados.
require_once 'config.php';

// --- ROTEAMENTO DE AÇÕES ---
// Determina qual ação deve ser executada, pegando o parâmetro 'acao' de POST ou GET.
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
// Obtém o ID do pedido principal que será modificado.
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);


// --- BLOCO: ATUALIZAR STATUS DO PEDIDO ---
if ($acao == 'atualizar_status' && $id > 0) {
    $novo_status = $_POST['status'] ?? '';
    // Lista de status permitidos para garantir que apenas valores válidos sejam salvos.
    $status_validos = ['novo', 'chamou', 'negociando', 'enviado', 'entregue', 'cancelado'];

    // Verifica se o status recebido está na lista de status válidos.
    if (in_array($novo_status, $status_validos)) {
        try {
            $stmt = $conn->prepare("UPDATE pedidos SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $novo_status, ':id' => $id]);
            // Redireciona de volta para a página de detalhes com uma mensagem de sucesso.
            header("Location: pedido_detalhes.php?id=$id&msg=Status atualizado!");
            exit();
        } catch (PDOException $e) {
            // Em caso de erro, redireciona com uma mensagem de erro.
            header("Location: pedido_detalhes.php?id=$id&err=Erro ao atualizar.");
            exit();
        }
    }
}

// --- BLOCO: ADICIONAR ITEM AO PEDIDO ---
if ($acao == 'adicionar_item' && $id > 0) {
    $sku = $_POST['sku'] ?? '';
    $quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 0;

    // Validação básica dos dados recebidos.
    if (empty($sku) || $quantidade <= 0) {
        header("Location: pedido_detalhes.php?id=$id&err=SKU ou quantidade inválida.");
        exit();
    }

    // Inicia uma transação. Isso garante que todas as queries seguintes sejam executadas
    // com sucesso, ou nenhuma será aplicada em caso de erro.
    $conn->beginTransaction();
    try {
        // 1. Busca o produto no banco para obter seu preço.
        $stmt_prod = $conn->prepare("SELECT price, promotional_price FROM produtos WHERE sku = :sku");
        $stmt_prod->execute([':sku' => $sku]);
        $produto = $stmt_prod->fetch(PDO::FETCH_ASSOC);

        if (!$produto) throw new Exception("Produto não encontrado.");

        // Define o preço a ser usado (promocional, se houver, senão o preço normal).
        $preco_unitario = $produto['promotional_price'] ?? $produto['price'];
        $preco_promocional = $produto['promotional_price'];

        // 2. Insere o novo item na tabela 'pedido_itens'.
        $stmt_insert = $conn->prepare("INSERT INTO pedido_itens (pedido_id, produto_sku, quantidade, preco_unitario, preco_promocional_unitario) VALUES (:pid, :sku, :qtd, :preco, :preco_promo)");
        $stmt_insert->execute([
            ':pid' => $id,
            ':sku' => $sku,
            ':qtd' => $quantidade,
            ':preco' => $preco_unitario,
            ':preco_promo' => $preco_promocional
        ]);

        // 3. Recalcula o valor_total na tabela 'pedidos'.
        $stmt_recalc = $conn->prepare("
            UPDATE pedidos SET valor_total = 
            (SELECT SUM(preco_unitario * quantidade) FROM pedido_itens WHERE pedido_id = :pid) + frete_valor 
            WHERE id = :pid
        ");
        $stmt_recalc->execute([':pid' => $id]);

        // Se tudo deu certo, confirma as alterações no banco de dados.
        $conn->commit();
        header("Location: pedido_detalhes.php?id=$id&msg=Item adicionado com sucesso!");
    } catch (Exception $e) {
        // Se qualquer passo falhou, desfaz todas as alterações feitas na transação.
        $conn->rollBack();
        header("Location: pedido_detalhes.php?id=$id&err=" . urlencode($e->getMessage()));
    }
    exit();
}

// --- BLOCO: REMOVER ITEM DO PEDIDO ---
if ($acao == 'remover_item') {
    $item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
    $pedido_id_redirect = isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : 0;

    // Valida se os IDs necessários foram recebidos.
    if ($item_id <= 0 || $pedido_id_redirect <= 0) {
        header("Location: pedidos.php?err=IDs inválidos.");
        exit();
    }

    // Inicia uma transação para garantir a integridade dos dados.
    $conn->beginTransaction();
    try {
        // 1. Deleta o item da tabela 'pedido_itens'.
        $stmt_delete = $conn->prepare("DELETE FROM pedido_itens WHERE id = :item_id");
        $stmt_delete->execute([':item_id' => $item_id]);

        // 2. Recalcula o valor_total do pedido.
        // COALESCE é usado para garantir que o resultado seja 0 se não houver mais itens no pedido, evitando um erro com SUM(NULL).
        $stmt_recalc = $conn->prepare("
            UPDATE pedidos SET valor_total = 
            COALESCE((SELECT SUM(preco_unitario * quantidade) FROM pedido_itens WHERE pedido_id = :pid), 0) + frete_valor 
            WHERE id = :pid
        ");
        $stmt_recalc->execute([':pid' => $pedido_id_redirect]);

        // Se tudo correu bem, salva as alterações.
        $conn->commit();
        header("Location: pedido_detalhes.php?id=$pedido_id_redirect&msg=Item removido!");
    } catch (Exception $e) {
        // Se algo deu errado, reverte as alterações.
        $conn->rollBack();
        header("Location: pedido_detalhes.php?id=$pedido_id_redirect&err=Erro ao remover item.");
    }
    exit();
}

// --- REDIRECIONAMENTO PADRÃO ---
// Se nenhuma ação válida for encontrada, redireciona para a página principal de pedidos.
header("Location: pedidos.php");
exit();
