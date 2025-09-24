<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// Busca todos os administradores
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

        <?php require_once 'templates/sidebar.php'; // CAMINHO ATUALIZADO 
        ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Gerenciar Administradores</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php"
                        class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Lista de Administradores</h2>
                    <a href="forms/form_admin.php?acao=adicionar"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
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
                                        <a href="forms/form_admin.php?acao=editar&id=<?php echo $admin['id']; ?>"
                                            class="text-blue-500 hover:text-blue-700 mx-2" title="Editar"><i
                                                class="fas fa-pencil-alt"></i></a>
                                        <?php if ($admin['usuario'] !== $username): ?>
                                            <a href="actions/acoes_admin.php?acao=deletar&id=<?php echo $admin['id']; ?>"
                                                class="text-red-500 hover:text-red-700 mx-2" title="Remover"
                                                onclick="return confirm('Tem certeza que deseja remover este administrador?');"><i
                                                    class="fas fa-trash-alt"></i></a>
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