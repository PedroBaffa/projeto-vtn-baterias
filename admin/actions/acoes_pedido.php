<?php

/**
 * @file acoes_pedido.php
 * Controlador para gerenciar ações relacionadas a um pedido específico (Caminhos Corrigidos).
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}
require_once '../config.php'; // CAMINHO ATUALIZADO

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

// --- BLOCO: ATUALIZAR STATUS DO PEDIDO ---
if ($acao == 'atualizar_status' && $id > 0) {
    $novo_status = $_POST['status'] ?? '';
    $status_validos = ['novo', 'chamou', 'negociando', 'enviado', 'entregue', 'cancelado'];

    if (in_array($novo_status, $status_validos)) {
        try {
            $stmt = $conn->prepare("UPDATE pedidos SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $novo_status, ':id' => $id]);
            header("Location: ../details/pedido_detalhes.php?id=$id&msg=Status atualizado!"); // CAMINHO ATUALIZADO
            exit();
        } catch (PDOException $e) {
            header("Location: ../details/pedido_detalhes.php?id=$id&err=Erro ao atualizar."); // CAMINHO ATUALIZADO
            exit();
        }
    }
}

// --- BLOCO: ADICIONAR ITEM AO PEDIDO ---
if ($acao == 'adicionar_item' && $id > 0) {
    $sku = $_POST['sku'] ?? '';
    $quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 0;

    if (empty($sku) || $quantidade <= 0) {
        header("Location: ../details/pedido_detalhes.php?id=$id&err=SKU ou quantidade inválida."); // CAMINHO ATUALIZADO
        exit();
    }

    $conn->beginTransaction();
    try {
        $stmt_prod = $conn->prepare("SELECT price, promotional_price FROM produtos WHERE sku = :sku");
        $stmt_prod->execute([':sku' => $sku]);
        $produto = $stmt_prod->fetch(PDO::FETCH_ASSOC);

        if (!$produto) throw new Exception("Produto não encontrado.");

        $preco_unitario = $produto['promotional_price'] ?? $produto['price'];
        $preco_promocional = $produto['promotional_price'];

        $stmt_insert = $conn->prepare("INSERT INTO pedido_itens (pedido_id, produto_sku, quantidade, preco_unitario, preco_promocional_unitario) VALUES (:pid, :sku, :qtd, :preco, :preco_promo)");
        $stmt_insert->execute([
            ':pid' => $id,
            ':sku' => $sku,
            ':qtd' => $quantidade,
            ':preco' => $preco_unitario,
            ':preco_promo' => $preco_promocional
        ]);

        $stmt_recalc = $conn->prepare("
            UPDATE pedidos SET valor_total = 
            (SELECT SUM(preco_unitario * quantidade) FROM pedido_itens WHERE pedido_id = :pid) + frete_valor 
            WHERE id = :pid
        ");
        $stmt_recalc->execute([':pid' => $id]);

        $conn->commit();
        header("Location: ../details/pedido_detalhes.php?id=$id&msg=Item adicionado com sucesso!"); // CAMINHO ATUALIZADO
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: ../details/pedido_detalhes.php?id=$id&err=" . urlencode($e->getMessage())); // CAMINHO ATUALIZADO
    }
    exit();
}

// --- BLOCO: REMOVER ITEM DO PEDIDO ---
if ($acao == 'remover_item') {
    $item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
    $pedido_id_redirect = isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : 0;

    if ($item_id <= 0 || $pedido_id_redirect <= 0) {
        header("Location: ../pedidos.php?err=IDs inválidos."); // CAMINHO ATUALIZADO
        exit();
    }

    $conn->beginTransaction();
    try {
        $stmt_delete = $conn->prepare("DELETE FROM pedido_itens WHERE id = :item_id");
        $stmt_delete->execute([':item_id' => $item_id]);

        $stmt_recalc = $conn->prepare("
            UPDATE pedidos SET valor_total = 
            COALESCE((SELECT SUM(preco_unitario * quantidade) FROM pedido_itens WHERE pedido_id = :pid), 0) + frete_valor 
            WHERE id = :pid
        ");
        $stmt_recalc->execute([':pid' => $pedido_id_redirect]);

        $conn->commit();
        header("Location: ../details/pedido_detalhes.php?id=$pedido_id_redirect&msg=Item removido!"); // CAMINHO ATUALIZADO
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: ../details/pedido_detalhes.php?id=$pedido_id_redirect&err=Erro ao remover item."); // CAMINHO ATUALIZADO
    }
    exit();
}

// --- REDIRECIONAMENTO PADRÃO ---
header("Location: ../pedidos.php"); // CAMINHO ATUALIZADO
exit();
