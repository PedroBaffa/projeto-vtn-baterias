<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// Lógica de Filtros
$filter_status = $_GET['status'] ?? 'todos'; // Padrão para mostrar as pendentes

$sql = "
    SELECT f.id, f.pergunta, f.status, f.data_pergunta, p.title as produto_titulo, u.nome as nome_cliente
    FROM faq_perguntas f
    JOIN produtos p ON f.produto_id = p.id
    LEFT JOIN usuarios u ON f.email_cliente = u.email
";

$params = [];
if ($filter_status !== 'todos') {
    $sql .= " WHERE f.status = :status";
    $params[':status'] = $filter_status;
}
$sql .= " ORDER BY f.data_pergunta DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusClass($status)
{
    switch ($status) {
        case 'pendente':
            return 'bg-yellow-100 text-yellow-800';
        case 'aprovada':
            return 'bg-green-100 text-green-800';
        case 'rejeitada':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Dúvidas (FAQ) - Painel</title>
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

        <?php require_once 'templates/sidebar.php'; ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Gerenciar Dúvidas (FAQ)</h1>
                <div class="flex items-center"><span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span><a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Sair</a></div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                    <form action="faq.php" method="GET">
                        <label for="status" class="mr-2 font-medium">Filtrar por status:</label>
                        <select name="status" id="status" class="border rounded-md p-2" onchange="this.form.submit()">
                            <option value="pendente" <?php if ($filter_status == 'pendente') echo 'selected'; ?>>Pendentes</option>
                            <option value="aprovada" <?php if ($filter_status == 'aprovada') echo 'selected'; ?>>Aprovadas</option>
                            <option value="rejeitada" <?php if ($filter_status == 'rejeitada') echo 'selected'; ?>>Rejeitadas</option>
                            <option value="todos" <?php if ($filter_status == 'todos') echo 'selected'; ?>>Todas</option>
                        </select>
                    </form>
                </div>
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Pergunta</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Produto</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Cliente</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Status</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($perguntas)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-gray-500">Nenhuma pergunta encontrada para este filtro.</td>
                                </tr>
                                <?php else: foreach ($perguntas as $pergunta): ?>
                                    <tr>
                                        <td class="p-3 text-sm text-gray-700 max-w-sm truncate" title="<?php echo htmlspecialchars($pergunta['pergunta']); ?>"><?php echo htmlspecialchars($pergunta['pergunta']); ?></td>
                                        <td class="p-3 text-sm text-gray-600"><?php echo htmlspecialchars($pergunta['produto_titulo']); ?></td>
                                        <td class="p-3 text-sm text-gray-600"><?php echo htmlspecialchars($pergunta['nome_cliente'] ?? 'Visitante'); ?></td>
                                        <td class="p-3 text-center text-xs"><span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo getStatusClass($pergunta['status']); ?>"><?php echo ucfirst($pergunta['status']); ?></span></td>
                                        <td class="p-3 text-center">
                                            <a href="forms/form_faq.php?id=<?php echo $pergunta['id']; ?>" class="text-blue-500 hover:text-blue-700 mx-2" title="Responder/Editar"><i class="fas fa-pencil-alt"></i></a>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>

</html>