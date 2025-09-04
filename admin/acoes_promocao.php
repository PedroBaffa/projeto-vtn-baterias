<?php

/**
 * @file
 * Gerencia as ações de Criar, Editar e Apagar (CRUD) para as Promoções.
 * Este script calcula e aplica os descontos diretamente nos produtos associados.
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam executar estas ações.
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

require_once 'config.php';

/**
 * Roteamento de Ações
 * Determina qual operação deve ser executada com base no parâmetro 'acao'.
 */
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// --- FUNÇÃO DE AJUDA (HELPER) ---

/**
 * Calcula e aplica o preço promocional aos produtos associados a uma promoção.
 *
 * @param PDO $conn A conexão com o banco de dados.
 * @param int $promocao_id O ID da promoção a ser aplicada.
 * @return void
 */
function aplicar_desconto($conn, $promocao_id)
{
    // 1. Busca o percentual de desconto da promoção.
    $stmt_promo = $conn->prepare("SELECT percentual_desconto FROM promocoes WHERE id = ?");
    $stmt_promo->execute([$promocao_id]);
    $percentual = $stmt_promo->fetchColumn();
    if (!$percentual) return; // Se a promoção não for encontrada, interrompe.

    $desconto = (float) $percentual / 100.0; // Converte o percentual para um multiplicador (ex: 15% -> 0.15).

    // 2. Busca todos os IDs de produtos associados a esta promoção.
    $stmt_produtos = $conn->prepare("SELECT produto_id FROM produto_promocao WHERE promocao_id = ?");
    $stmt_produtos->execute([$promocao_id]);
    $produto_ids = $stmt_produtos->fetchAll(PDO::FETCH_COLUMN, 0);

    if (empty($produto_ids)) return; // Se não houver produtos, não há o que fazer.

    // 3. Atualiza o campo 'promotional_price' de todos os produtos de uma só vez.
    // A query calcula o preço com desconto (preço * (1 - fator_de_desconto)).
    $placeholders = implode(',', array_fill(0, count($produto_ids), '?'));
    $stmt_update = $conn->prepare(
        "UPDATE produtos SET promotional_price = price * (1 - ?) WHERE id IN ($placeholders)"
    );
    // Combina o valor do desconto com o array de IDs para a execução da query.
    $params = array_merge([$desconto], $produto_ids);
    $stmt_update->execute($params);
}

// --- AÇÕES DO CRUD ---

// --- AÇÃO: CRIAR PROMOÇÃO ---
if ($acao == 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inicia uma transação para garantir a integridade dos dados.
    $conn->beginTransaction();
    try {
        // 1. Insere a nova promoção na tabela 'promocoes'.
        $stmt = $conn->prepare("INSERT INTO promocoes (nome, percentual_desconto, data_inicio, data_fim) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['percentual_desconto'], $_POST['data_inicio'], $_POST['data_fim']]);
        $id_promocao = $conn->lastInsertId();

        // 2. Associa os produtos selecionados à nova promoção.
        if (!empty($_POST['produtos'])) {
            $stmt_assoc = $conn->prepare("INSERT INTO produto_promocao (produto_id, promocao_id) VALUES (?, ?)");
            foreach ($_POST['produtos'] as $id_produto) {
                $stmt_assoc->execute([(int)$id_produto, $id_promocao]);
            }
        }

        // 3. Chama a função para calcular e aplicar os descontos.
        aplicar_desconto($conn, $id_promocao);

        // Se tudo ocorreu bem, confirma as alterações no banco de dados.
        $conn->commit();
        header("Location: promocoes.php?msg=Promoção criada com sucesso!");
    } catch (Exception $e) {
        // Se ocorreu qualquer erro, desfaz todas as operações.
        $conn->rollBack();
        header("Location: form_promocao.php?err=" . urlencode($e->getMessage()));
    }
    exit();
}

// --- AÇÃO: EDITAR PROMOÇÃO ---
if ($acao == 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_promocao = (int)$_POST['id'];
    $conn->beginTransaction();
    try {
        // 1. Limpa o preço promocional de todos os produtos que estavam associados a esta promoção.
        // Isso é crucial para remover descontos de produtos que foram desmarcados.
        $conn->prepare("UPDATE produtos p JOIN produto_promocao pp ON p.id = pp.produto_id SET p.promotional_price = NULL WHERE pp.promocao_id = ?")->execute([$id_promocao]);

        // 2. Atualiza os dados principais da promoção.
        $stmt = $conn->prepare("UPDATE promocoes SET nome = ?, percentual_desconto = ?, data_inicio = ?, data_fim = ? WHERE id = ?");
        $stmt->execute([$_POST['nome'], $_POST['percentual_desconto'], $_POST['data_inicio'], $_POST['data_fim'], $id_promocao]);

        // 3. Remove todas as associações antigas para recriá-las com a nova seleção de produtos.
        $conn->prepare("DELETE FROM produto_promocao WHERE promocao_id = ?")->execute([$id_promocao]);
        if (!empty($_POST['produtos'])) {
            $stmt_assoc = $conn->prepare("INSERT INTO produto_promocao (produto_id, promocao_id) VALUES (?, ?)");
            foreach ($_POST['produtos'] as $id_produto) {
                $stmt_assoc->execute([(int)$id_produto, $id_promocao]);
            }
        }

        // 4. Reaplica os descontos para a nova lista de produtos.
        aplicar_desconto($conn, $id_promocao);

        $conn->commit();
        header("Location: promocoes.php?msg=Promoção atualizada com sucesso!");
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: form_promocao.php?id=$id_promocao&err=" . urlencode($e->getMessage()));
    }
    exit();
}

// --- AÇÃO: DELETAR PROMOÇÃO ---
if ($acao == 'deletar') {
    $id_promocao = (int)$_GET['id'];
    $conn->beginTransaction();
    try {
        // 1. IMPORTANTE: Remove o preço promocional dos produtos afetados ANTES de deletar a promoção.
        // Se a promoção fosse deletada primeiro, perderíamos a referência de quais produtos atualizar.
        $conn->prepare("UPDATE produtos p JOIN produto_promocao pp ON p.id = pp.produto_id SET p.promotional_price = NULL WHERE pp.promocao_id = ?")->execute([$id_promocao]);

        // 2. Deleta a promoção. A configuração de 'ON DELETE CASCADE' no banco de dados
        // garante que todas as entradas na tabela `produto_promocao` relacionadas a este ID
        // também sejam automaticamente removidas.
        $conn->prepare("DELETE FROM promocoes WHERE id = ?")->execute([$id_promocao]);

        $conn->commit();
        header("Location: promocoes.php?msg=Promoção removida com sucesso!");
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: promocoes.php?err=" . urlencode($e->getMessage()));
    }
    exit();
}

// Redirecionamento padrão final.
exit();
