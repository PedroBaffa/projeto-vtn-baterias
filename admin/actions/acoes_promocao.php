<?php
// Arquivo: admin/actions/acoes_promocao.php (Caminhos Corrigidos)

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}
require_once '../config.php'; // CAMINHO ATUALIZADO

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// Função para calcular e aplicar o desconto a uma lista de IDs de produtos
function aplicar_desconto($conn, $promocao_id)
{
    $stmt_promo = $conn->prepare("SELECT percentual_desconto FROM promocoes WHERE id = ?");
    $stmt_promo->execute([$promocao_id]);
    $percentual = $stmt_promo->fetchColumn();
    if (!$percentual) return;

    $desconto = (float) $percentual / 100.0;

    $stmt_produtos = $conn->prepare("SELECT produto_id FROM produto_promocao WHERE promocao_id = ?");
    $stmt_produtos->execute([$promocao_id]);
    $produto_ids = $stmt_produtos->fetchAll(PDO::FETCH_COLUMN, 0);

    if (empty($produto_ids)) return;

    $placeholders = implode(',', array_fill(0, count($produto_ids), '?'));
    $stmt_update = $conn->prepare(
        "UPDATE produtos SET promotional_price = price * (1 - ?) WHERE id IN ($placeholders)"
    );
    $params = array_merge([$desconto], $produto_ids);
    $stmt_update->execute($params);
}

// --- Ações ---

if ($acao == 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("INSERT INTO promocoes (nome, percentual_desconto, data_inicio, data_fim) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['percentual_desconto'], $_POST['data_inicio'], $_POST['data_fim']]);
        $id_promocao = $conn->lastInsertId();

        if (!empty($_POST['produtos'])) {
            $stmt_assoc = $conn->prepare("INSERT INTO produto_promocao (produto_id, promocao_id) VALUES (?, ?)");
            foreach ($_POST['produtos'] as $id_produto) {
                $stmt_assoc->execute([(int)$id_produto, $id_promocao]);
            }
        }

        aplicar_desconto($conn, $id_promocao);

        $conn->commit();
        header("Location: ../promocoes.php?msg=Promoção criada com sucesso!"); // CAMINHO ATUALIZADO
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: ../forms/form_promocao.php?err=" . urlencode($e->getMessage())); // CAMINHO ATUALIZADO
    }
}

if ($acao == 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_promocao = (int)$_POST['id'];
    $conn->beginTransaction();
    try {
        // Zera promoções antigas
        $conn->prepare("UPDATE produtos p JOIN produto_promocao pp ON p.id = pp.produto_id SET p.promotional_price = NULL WHERE pp.promocao_id = ?")->execute([$id_promocao]);

        $stmt = $conn->prepare("UPDATE promocoes SET nome = ?, percentual_desconto = ?, data_inicio = ?, data_fim = ? WHERE id = ?");
        $stmt->execute([$_POST['nome'], $_POST['percentual_desconto'], $_POST['data_inicio'], $_POST['data_fim'], $id_promocao]);

        // Remove associações antigas e insere as novas
        $conn->prepare("DELETE FROM produto_promocao WHERE promocao_id = ?")->execute([$id_promocao]);
        if (!empty($_POST['produtos'])) {
            $stmt_assoc = $conn->prepare("INSERT INTO produto_promocao (produto_id, promocao_id) VALUES (?, ?)");
            foreach ($_POST['produtos'] as $id_produto) {
                $stmt_assoc->execute([(int)$id_produto, $id_promocao]);
            }
        }

        aplicar_desconto($conn, $id_promocao);

        $conn->commit();
        header("Location: ../promocoes.php?msg=Promoção atualizada com sucesso!"); // CAMINHO ATUALIZADO
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: ../forms/form_promocao.php?id=$id_promocao&err=" . urlencode($e->getMessage())); // CAMINHO ATUALIZADO
    }
}

if ($acao == 'deletar') {
    $id_promocao = (int)$_GET['id'];
    $conn->beginTransaction();
    try {
        // Zera o preço promocional dos produtos afetados ANTES de deletar
        $conn->prepare("UPDATE produtos p JOIN produto_promocao pp ON p.id = pp.produto_id SET p.promotional_price = NULL WHERE pp.promocao_id = ?")->execute([$id_promocao]);

        // Deleta a promoção (as associações são deletadas em cascata pela configuração do DB)
        $conn->prepare("DELETE FROM promocoes WHERE id = ?")->execute([$id_promocao]);

        $conn->commit();
        header("Location: ../promocoes.php?msg=Promoção removida com sucesso!"); // CAMINHO ATUALIZADO
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: ../promocoes.php?err=" . urlencode($e->getMessage())); // CAMINHO ATUALIZADO
    }
}

exit();
