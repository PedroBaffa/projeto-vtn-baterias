<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Corrigido
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once '../config.php'; // Corrigido

// Pega o ID do usuário da URL e garante que é um número
$usuario_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($usuario_id === 0) {
    header("Location: ../usuarios.php?err=ID de usuário inválido."); // Corrigido
    exit();
}

// Busca todos os dados do usuário específico no banco de dados
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Se nenhum usuário for encontrado com esse ID, volta para a lista
if (!$usuario) {
    header("Location: ../usuarios.php?err=Usuário não encontrado."); // Corrigido
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Detalhes do Usuário - Painel</title>
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
                <h1 class="text-2xl font-semibold text-gray-700">Detalhes do Usuário</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="../logout.php"
                        class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8 max-w-2xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="border-b md:border-b-0 md:border-r pb-6 md:pb-0 md:pr-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informações Pessoais</h3>
                            <p class="text-gray-600"><strong class="font-medium text-gray-800">Nome:</strong>
                                <?php echo htmlspecialchars($usuario['nome']); ?></p>
                            <p class="text-gray-600 mt-2"><strong class="font-medium text-gray-800">Sobrenome:</strong>
                                <?php echo htmlspecialchars($usuario['sobrenome']); ?></p>
                            <p class="text-gray-600 mt-2"><strong class="font-medium text-gray-800">CPF:</strong>
                                <?php echo htmlspecialchars($usuario['cpf']); ?></p>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informações de Contato
                            </h3>
                            <p class="text-gray-600"><strong class="font-medium text-gray-800">Email:</strong>
                                <?php echo htmlspecialchars($usuario['email']); ?></p>
                            <p class="text-gray-600 mt-2"><strong class="font-medium text-gray-800">Telefone:</strong>
                                <?php echo htmlspecialchars($usuario['telefone']); ?></p>
                            <p class="text-gray-600 mt-2"><strong class="font-medium text-gray-800">Endereço:</strong>
                                <?php echo nl2br(htmlspecialchars($usuario['endereco'] ?: 'Não informado')); ?></p>
                        </div>
                    </div>
                    <div class="border-t mt-6 pt-6">
                        <p class="text-sm text-gray-500"><strong class="font-medium text-gray-700">Data de
                                Cadastro:</strong>
                            <?php echo date('d/m/Y \à\s H:i', strtotime($usuario['data_cadastro'])); ?></p>
                    </div>
                    <div class="mt-8 text-right">
                        <a href="../usuarios.php"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg transition">Voltar
                            para a Lista</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>