<?php

/**
 * @file
 * Processa a exclusão de contatos (leads) a partir do painel de administração.
 * Este script é acionado por um link na página de listagem de contatos.
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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Converte o ID para inteiro por segurança.

// --- LÓGICA PARA DELETAR UM CONTATO ---
// Verifica se a ação é 'deletar' e se um ID válido foi fornecido.
if ($acao == 'deletar' && $id > 0) {
    try {
        // Prepara a instrução SQL para deletar o contato com o ID correspondente.
        $stmt = $conn->prepare("DELETE FROM contatos WHERE id = :id");
        // Executa a query, substituindo o placeholder ':id' pelo valor da variável $id.
        $stmt->execute([':id' => $id]);

        // Se a exclusão for bem-sucedida, redireciona de volta para a lista de contatos
        // com uma mensagem de sucesso na URL.
        header("Location: contatos.php?msg=Contato removido com sucesso!");
        exit();
    } catch (PDOException $e) {
        // Em caso de erro no banco de dados, redireciona de volta com uma mensagem de erro.
        // Em um ambiente de produção, seria ideal logar o erro $e->getMessage() em um arquivo.
        header("Location: contatos.php?err=Erro ao remover o contato.");
        exit();
    }
}

// Se o script for acessado sem a ação 'deletar' ou sem um ID válido,
// ele simplesmente redireciona o usuário para a página principal de contatos.
header("Location: contatos.php");
exit();
