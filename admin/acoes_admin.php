<?php

/**
 * @file
 * Gerencia as ações de Criar, Editar e Apagar (CRUD) para os administradores do painel.
 * Este script é chamado pelos formulários da área de administração e não gera HTML.
 * Apenas processa os dados e redireciona o usuário de volta para a lista de admins.
 */

// Inicia a sessão para acessar as variáveis de login.
session_start();

// Medida de segurança: Garante que apenas usuários logados possam executar estas ações.
if (!isset($_SESSION['user_id'])) {
    // Se não houver uma sessão ativa, interrompe o script com uma mensagem de erro.
    die("Acesso negado.");
}

// Inclui o arquivo de configuração que estabelece a conexão com o banco de dados.
require_once 'config.php';

/**
 * Roteamento de Ações
 * Determina qual operação (adicionar, editar, deletar) deve ser executada
 * com base no parâmetro 'acao' recebido via POST ou GET.
 */
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// --- LÓGICA PARA ADICIONAR NOVO ADMINISTRADOR ---
if ($acao == 'adicionar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação: Verifica se os campos essenciais não estão vazios.
    if (empty($_POST['usuario']) || empty($_POST['senha'])) {
        header("Location: form_admin.php?acao=adicionar&err=Todos os campos são obrigatórios.");
        exit();
    }

    $usuario = $_POST['usuario'];
    // Segurança: Criptografa a senha antes de salvá-la no banco de dados.
    // password_hash() é a forma moderna e segura de armazenar senhas.
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    try {
        // Prepara e executa a query SQL para inserir o novo administrador.
        $stmt = $conn->prepare("INSERT INTO administradores (usuario, senha) VALUES (:usuario, :senha)");
        $stmt->execute([':usuario' => $usuario, ':senha' => $senha_hash]);

        // Redireciona para a página de administradores com uma mensagem de sucesso.
        header("Location: admins.php?msg=Admin adicionado com sucesso!");
        exit();
    } catch (PDOException $e) {
        // Tratamento de erro: Verifica se o erro é de "entrada duplicada" (usuário já existe).
        if ($e->errorInfo[1] == 1062) { // 1062 é o código de erro do MySQL para 'Duplicate entry'.
            header("Location: form_admin.php?acao=adicionar&err=Este nome de usuário já existe.");
        } else {
            // Para qualquer outro erro, exibe uma mensagem genérica.
            header("Location: form_admin.php?acao=adicionar&err=Erro ao adicionar no banco de dados.");
        }
        exit();
    }
}

// --- LÓGICA PARA EDITAR UM ADMINISTRADOR EXISTENTE ---
if ($acao == 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação: Garante que os dados necessários foram enviados.
    if (empty($_POST['id']) || empty($_POST['usuario'])) {
        header("Location: admins.php?err=Dados insuficientes.");
        exit();
    }

    $id = (int) $_POST['id'];
    $usuario = $_POST['usuario'];
    $nova_senha = $_POST['senha'];

    try {
        // Lógica condicional: A senha só é atualizada se um novo valor for fornecido.
        // Isso permite alterar o nome de usuário sem precisar redefinir a senha.
        if (!empty($nova_senha)) {
            // Se uma nova senha foi digitada, criptografa e atualiza o usuário e a senha.
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE administradores SET usuario = :usuario, senha = :senha WHERE id = :id");
            $stmt->execute([':usuario' => $usuario, ':senha' => $senha_hash, ':id' => $id]);
        } else {
            // Se o campo de senha estiver vazio, atualiza apenas o nome de usuário.
            $stmt = $conn->prepare("UPDATE administradores SET usuario = :usuario WHERE id = :id");
            $stmt->execute([':usuario' => $usuario, ':id' => $id]);
        }

        header("Location: admins.php?msg=Admin atualizado com sucesso!");
        exit();
    } catch (PDOException $e) {
        // Tratamento de erro de duplicidade, caso o novo nome de usuário já exista.
        if ($e->errorInfo[1] == 1062) {
            header("Location: form_admin.php?acao=editar&id={$id}&err=Este nome de usuário já pertence a outro admin.");
        } else {
            header("Location: form_admin.php?acao=editar&id={$id}&err=Erro ao atualizar no banco de dados.");
        }
        exit();
    }
}

// --- LÓGICA PARA DELETAR UM ADMINISTRADOR ---
if ($acao == 'deletar') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // Medida de segurança crítica: Impede que um usuário delete a própria conta.
    // Pega o ID do usuário logado na sessão e compara com o ID a ser deletado.
    $id_usuario_logado = $_SESSION['user_id'];

    if ($id > 0 && $id != $id_usuario_logado) {
        try {
            $stmt = $conn->prepare("DELETE FROM administradores WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header("Location: admins.php?msg=Admin removido!");
            exit();
        } catch (PDOException $e) {
            header("Location: admins.php?err=Erro ao remover.");
            exit();
        }
    } else {
        // Se a tentativa for de auto-exclusão, redireciona com uma mensagem de erro.
        header("Location: admins.php?err=Não é possível remover a própria conta.");
        exit();
    }
}

// Redirecionamento padrão caso nenhuma ação seja correspondida.
header("Location: admins.php");
exit();
