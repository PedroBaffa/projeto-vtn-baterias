<?php
// Arquivo: admin/actions/acoes_usuario_admin.php (Caminhos Corrigidos)
session_start();
// Apenas administradores logados podem executar esta ação
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

require_once '../config.php'; // CAMINHO ATUALIZADO

$acao = $_GET['acao'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// --- LÓGICA PARA DELETAR UM USUÁRIO ---
if ($acao == 'deletar' && $id > 0) {
    try {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: ../usuarios.php?msg=Usuário removido com sucesso!"); // CAMINHO ATUALIZADO
        exit();
    } catch (PDOException $e) {
        header("Location: ../usuarios.php?err=Erro ao remover o usuário."); // CAMINHO ATUALIZADO
    }
}

// Redirecionamento padrão
header("Location: ../usuarios.php"); // CAMINHO ATUALIZADO
exit();
