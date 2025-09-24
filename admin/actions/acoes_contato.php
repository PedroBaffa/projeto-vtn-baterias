<?php
// Arquivo: admin/actions/acoes_contato.php (Caminhos Corrigidos)

session_start();
// Apenas usuários logados podem executar esta ação
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

// CAMINHO ATUALIZADO: Volta um nível para encontrar o config.php
require_once '../config.php';

$acao = $_GET['acao'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- LÓGICA PARA DELETAR UM CONTATO ---
if ($acao == 'deletar' && $id > 0) {
    try {
        $stmt = $conn->prepare("DELETE FROM contatos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // CAMINHO ATUALIZADO para a página de contatos
        header("Location: ../contatos.php?msg=Contato removido com sucesso!");
        exit();
    } catch (PDOException $e) {
        // CAMINHO ATUALIZADO para a página de contatos
        header("Location: ../contatos.php?err=Erro ao remover o contato.");
        exit();
    }
}

// Se nenhuma ação válida for fornecida, apenas redireciona de volta
// CAMINHO ATUALIZADO para a página de contatos
header("Location: ../contatos.php");
exit();
