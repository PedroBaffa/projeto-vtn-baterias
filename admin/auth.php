<?php
// Arquivo: admin/auth.php (Caminhos Corrigidos)

// Inicia a sessão
session_start();

// --- 1. USA A CONEXÃO CENTRALIZADA COM O BANCO DE DADOS ---
require_once 'config.php';

// --- 2. VERIFICA SE O FORMULÁRIO FOI ENVIADO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // --- 3. BUSCA O USUÁRIO NO BANCO DE DADOS ---
    $stmt = $conn->prepare("SELECT id, usuario, senha FROM administradores WHERE usuario = :username");
    $stmt->bindParam(':username', $input_username);
    $stmt->execute();

    // Verifica se encontrou um usuário
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // --- 4. VERIFICA A SENHA ---
        // Compara a senha enviada com o hash salvo no banco
        if (password_verify($input_password, $user['senha'])) {
            // Senha correta! Login bem-sucedido.

            // Armazena os dados do usuário na sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];

            // Redireciona para o painel principal. O caminho está correto.
            header("Location: visao_geral.php");
            exit();
        }
    }

    // Se chegou até aqui, o usuário ou a senha estão incorretos. O caminho está correto.
    header("Location: login.php?error=Usuário ou senha inválidos.");
    exit();
} else {
    // Se alguém tentar acessar o arquivo diretamente, redireciona para o login. O caminho está correto.
    header("Location: login.php");
    exit();
}
