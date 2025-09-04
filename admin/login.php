<?php

/**
 * @file
 * Página de login para o painel administrativo.
 * Exibe o formulário de autenticação e mensagens de erro, se houver.
 */

// Inicia a sessão para verificar se o usuário já está logado.
session_start();

// Se o usuário já tiver uma sessão de login ativa, redireciona-o
// diretamente para o painel principal, evitando que ele veja a tela de login novamente.
if (isset($_SESSION['user_id'])) {
    header("Location: visao_geral.php");
    exit();
}

// Pega a mensagem de erro da URL (enviada pelo script 'auth.php' em caso de falha no login)
// e a prepara para ser exibida de forma segura na página.
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Administrativo</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <img src="../assets/img/logo.png" alt="Logo VTN" class="mx-auto h-16 mb-4">
            <h1 class="text-2xl font-bold text-gray-700">Painel Administrativo</h1>
            <p class="text-gray-500">Faça login para continuar</p>
        </div>

        <form action="auth.php" method="POST">
            <div class="mb-4">
                <label for="username" class="block text-gray-600 font-medium mb-2">Usuário</label>
                <input type="text" id="username" name="username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-600 font-medium mb-2">Senha</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition">
            </div>

            <?php
            // Bloco condicional: Se a variável $error não estiver vazia,
            // este bloco de HTML será renderizado para exibir a mensagem de erro.
            if (!empty($error)) :
            ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <div>
                <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 transition-transform transform hover:scale-105">
                    Entrar
                </button>
            </div>
        </form>
    </div>

</body>

</html>