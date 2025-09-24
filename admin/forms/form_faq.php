<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once '../config.php';

$pergunta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pergunta_id === 0) {
    header("Location: ../faq.php?err=ID de pergunta inválido.");
    exit();
}

$stmt = $conn->prepare("
    SELECT f.*, p.title as produto_titulo
    FROM faq_perguntas f
    JOIN produtos p ON f.produto_id = p.id
    WHERE f.id = :id
");
$stmt->execute([':id' => $pergunta_id]);
$pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pergunta) {
    header("Location: ../faq.php?err=Pergunta não encontrada.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Responder Dúvida - Painel</title>
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

        <?php require_once '../templates/sidebar.php'; ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Responder Dúvida</h1>
                <div class="flex items-center"><span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span><a href="../logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Sair</a></div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg p-8 max-w-2xl mx-auto">
                    <div class="mb-6 border-b pb-4">
                        <p class="text-sm text-gray-500">Produto:</p>
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($pergunta['produto_titulo']); ?></h2>
                        <p class="text-sm text-gray-500 mt-4">Cliente:</p>
                        <h3 class="text-lg font-semibold text-gray-700"><?php echo htmlspecialchars($pergunta['nome_cliente']); ?> (<?php echo htmlspecialchars($pergunta['email_cliente']); ?>)</h3>
                        <p class="text-sm text-gray-500 mt-4">Pergunta:</p>
                        <blockquote class="mt-2 p-4 bg-gray-50 rounded-lg border-l-4 border-gray-300">
                            <?php echo nl2br(htmlspecialchars($pergunta['pergunta'])); ?>
                        </blockquote>
                    </div>

                    <form action="../actions/acoes_faq_admin.php" method="POST">
                        <input type="hidden" name="acao" value="responder">
                        <input type="hidden" name="id" value="<?php echo $pergunta['id']; ?>">

                        <div class="mb-4">
                            <label for="resposta" class="block text-gray-600 font-medium mb-2">Sua Resposta</label>
                            <textarea id="resposta" name="resposta" rows="6" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required><?php echo htmlspecialchars($pergunta['resposta'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-6">
                            <label for="status" class="block text-gray-600 font-medium mb-2">Status</label>
                            <select id="status" name="status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="pendente" <?php if ($pergunta['status'] == 'pendente') echo 'selected'; ?>>Pendente</option>
                                <option value="aprovada" <?php if ($pergunta['status'] == 'aprovada') echo 'selected'; ?>>Aprovada (Exibir no site)</option>
                                <option value="rejeitada" <?php if ($pergunta['status'] == 'rejeitada') echo 'selected'; ?>>Rejeitada (Não exibir)</option>
                            </select>
                        </div>
                        <div class="mt-8 flex justify-end gap-4">
                            <a href="../faq.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded-lg">Cancelar</a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg">Salvar Resposta</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>