<?php

/**
 * @file
 * Página para visualizar e gerenciar os administradores do painel.
 * Exibe uma lista de todos os administradores e oferece opções para editar ou remover.
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam acessar esta página.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém o nome de usuário da sessão para exibição e para a lógica de segurança.
// A função htmlspecialchars() previne ataques de Cross-Site Scripting (XSS).
$username = htmlspecialchars($_SESSION['username']);

// Inclui o arquivo de configuração para conectar ao banco de dados.
// NOTA: A conexão PDO já está sendo criada no config.php, então a nova conexão aqui foi removida para evitar redundância.
require_once 'config.php';

// Busca no banco de dados todos os administradores, ordenados alfabeticamente.
$stmt = $conn->query("SELECT id, usuario FROM administradores ORDER BY usuario ASC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Administradores - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <div class="w-64 bg-white shadow-md sticky top-0 h-screen">
            <div class="p-6 text-center border-b">
                <a href="visao_geral.php"><img src="../assets/img/logo.png" alt="Logo VTN" class="mx-auto h-12"></a>
            </div>
            <nav class="mt-4">
                <a href="visao_geral.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-chart-pie w-6 text-center"></i><span class="mx-3">Visão Geral</span></a>
                <a href="dashboard.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-box-open w-6 text-center"></i><span class="mx-3">Produtos</span></a>
                <a href="importar.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-file-upload w-6 text-center"></i><span class="mx-3">Importar CSV</span></a>
                <a href="admins.php" class="flex items-center py-2 px-6 bg-gray-200 text-gray-800 font-semibold"><i class="fas fa-users-cog w-6 text-center"></i><span class="mx-3">Admins</span></a>
                <a href="galeria.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-images w-6 text-center"></i><span class="mx-3">Galeria</span></a>
                <a href="contatos.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-address-book w-6 text-center"></i><span class="mx-3">Contatos</span></a>
                <a href="usuarios.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200"><i class="fas fa-users w-6 text-center"></i><span class="mx-3">Usuários</span></a>
                <a href="promocoes.php" class="flex items-center py-2 px-6 text-gray-600 hover:bg-gray-200">
                    <i class="fas fa-tags w-6 text-center"></i><span class="mx-3">Promoções</span>
                </a>
            </nav>
        </div>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Gerenciar Administradores</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Lista de Administradores</h2>
                    <a href="form_admin.php?acao=adicionar" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        <i class="fas fa-user-plus mr-2"></i> Adicionar Admin
                    </a>
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Usuário</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td class="p-3 text-sm text-gray-700 font-medium">
                                        <?php echo htmlspecialchars($admin['usuario']); ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="form_admin.php?acao=editar&id=<?php echo $admin['id']; ?>" class="text-blue-500 hover:text-blue-700 mx-2" title="Editar"><i class="fas fa-pencil-alt"></i></a>

                                        <?php
                                        // Lógica de segurança: O botão de deletar só aparece se o admin da linha
                                        // NÃO for o mesmo que está logado, impedindo a autoexclusão.
                                        if ($admin['usuario'] !== $username):
                                        ?>
                                            <a href="acoes_admin.php?acao=deletar&id=<?php echo $admin['id']; ?>" class="text-red-500 hover:text-red-700 mx-2" title="Remover" onclick="return confirm('Tem certeza que deseja remover este administrador?');"><i class="fas fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>

</html>