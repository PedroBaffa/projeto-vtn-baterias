<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Corrigido
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once '../config.php'; // Corrigido: Volta um nível para encontrar o config.php

$modo_edicao = (isset($_GET['acao']) && $_GET['acao'] == 'editar' && isset($_GET['id']));
$admin_existente = ['id' => '', 'usuario' => ''];

if ($modo_edicao) {
    $id_admin = (int) $_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT id, usuario FROM administradores WHERE id = :id");
        $stmt->execute([':id' => $id_admin]);
        $admin_existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin_existente) {
            header("Location: ../admins.php?err=Administrador não encontrado."); // Corrigido
            exit();
        }
    } catch (PDOException $e) {
        die("Erro ao buscar admin: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo $modo_edicao ? 'Editar' : 'Adicionar'; ?> Administrador - Painel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../../assets/img/favicon.png">
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

        <?php require_once '../templates/sidebar.php'; // CAMINHO ATUALIZADO 
        ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700"><?php echo $modo_edicao ? 'Editar' : 'Adicionar'; ?> Administrador</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="../logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="max-w-lg mx-auto bg-white shadow-md rounded-lg p-8">
                    <form action="../actions/acoes_admin.php" method="POST">
                        <input type="hidden" name="acao" value="<?php echo $modo_edicao ? 'editar' : 'adicionar'; ?>">
                        <?php if ($modo_edicao): ?>
                            <input type="hidden" name="id" value="<?php echo $admin_existente['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-4">
                            <label for="usuario" class="block text-gray-600 font-medium mb-2">Nome de Usuário</label>
                            <input type="text" id="usuario" name="usuario" required
                                value="<?php echo htmlspecialchars($admin_existente['usuario']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div class="mb-6">
                            <label for="senha" class="block text-gray-600 font-medium mb-2">
                                <?php echo $modo_edicao ? 'Nova Senha (deixe em branco para não alterar)' : 'Senha'; ?>
                            </label>
                            <input type="password" id="senha" name="senha" <?php echo !$modo_edicao ? 'required' : ''; ?>
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div class="mt-8 flex justify-end">
                            <a href="../admins.php"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg mr-4 transition">Cancelar</a>
                            <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition">Salvar</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>