<?php

/**
 * @file
 * Processa a tentativa de login de um administrador.
 * Valida o usuário e a senha contra os dados armazenados no banco de dados.
 */

// Inicia a sessão para que possamos armazenar as informações do usuário após o login bem-sucedido.
session_start();

// 1. Inclui o arquivo de configuração que estabelece a conexão com o banco de dados.
require_once 'config.php';

// 2. Medida de segurança: Verifica se o script foi acessado através do método POST, o que indica um envio de formulário.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta os dados enviados pelo formulário de login.
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // 3. Busca no banco de dados um administrador com o nome de usuário fornecido.
    // Usar prepared statements (prepare/execute) previne SQL Injection.
    $stmt = $conn->prepare("SELECT id, usuario, senha FROM administradores WHERE usuario = :username");
    $stmt->bindParam(':username', $input_username);
    $stmt->execute();

    // Verifica se exatamente um usuário foi encontrado.
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. Segurança: Verifica a senha.
        // A função password_verify() compara a senha digitada pelo usuário com o hash
        // seguro armazenado no banco de dados. É a forma correta de validar senhas.
        if (password_verify($input_password, $user['senha'])) {
            // Se a senha estiver correta, o login é bem-sucedido.

            // Armazena o ID e o nome do usuário na sessão.
            // Estes dados serão usados em outras páginas para verificar se o usuário está logado.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];

            // Redireciona o administrador para a página principal do painel.
            header("Location: visao_geral.php");
            exit();
        }
    }

    // Se o script chegou até aqui, significa que o usuário não foi encontrado ou a senha estava incorreta.
    // Redireciona de volta para a página de login com uma mensagem de erro.
    header("Location: login.php?error=Usuário ou senha inválidos.");
    exit();
} else {
    // Se alguém tentar acessar este arquivo diretamente via URL (método GET),
    // redireciona para a página de login sem processar nada.
    header("Location: login.php");
    exit();
}
