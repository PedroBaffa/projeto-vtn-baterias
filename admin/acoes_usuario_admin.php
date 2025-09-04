<?php

/**
 * @file
 * Processa a exclusão de usuários cadastrados a partir do painel de administração.
 * Este script é acionado por um link na página de listagem de usuários.
 */

// Inicia a sessão para validar o login do administrador.
session_start();

// Medida de segurança: Garante que apenas administradores logados possam executar esta ação.
if (!isset($_SESSION['user_id'])) {
    // Interrompe a execução se o usuário não estiver autenticado.
    die("Acesso negado.");
}

// Inclui o arquivo de configuração para conectar ao banco de dados.
require_once 'config.php';

/**
 * Roteamento de Ações
 * Define a ação a ser executada com base nos parâmetros da URL (GET).
 */
$acao = $_GET['acao'] ?? '';
// Garante que o ID seja tratado como um número inteiro para segurança.
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// --- LÓGICA PARA DELETAR UM USUÁRIO ---
// Verifica se a ação solicitada é 'deletar' e se um ID de usuário válido foi fornecido.
if ($acao == 'deletar' && $id > 0) {
    try {
        // Prepara a instrução SQL para deletar o usuário com o ID correspondente.
        // A configuração 'ON DELETE CASCADE' na tabela 'carrinho_itens' garante que
        // os itens de carrinho deste usuário também sejam removidos automaticamente.
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // Redireciona de volta para a lista de usuários com uma mensagem de sucesso.
        header("Location: usuarios.php?msg=Usuário removido com sucesso!");
        exit();
    } catch (PDOException $e) {
        // Em caso de erro, redireciona com uma mensagem de falha.
        header("Location: usuarios.php?err=Erro ao remover o usuário.");
        exit();
    }
}

// Se o script for acessado sem os parâmetros corretos, redireciona para a página de usuários.
header("Location: usuarios.php");
exit();
