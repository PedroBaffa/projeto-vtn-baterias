<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// Busca todos os pedidos com informações do usuário
$stmt = $conn->query("
    SELECT p.id, p.valor_total, p.status, p.data_pedido, u.nome, u.sobrenome
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY p.data_pedido DESC
");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para definir a cor do status
function getStatusClass($status)
{
    switch ($status) {
        case 'novo':
            return 'bg-blue-100 text-blue-700';
        case 'chamou':
            return 'bg-yellow-100 text-yellow-700';
        case 'negociando':
            return 'bg-purple-100 text-purple-700';
        case 'enviado':
            return 'bg-indigo-100 text-indigo-700';
        case 'entregue':
            return 'bg-green-100 text-green-700';
        case 'cancelado':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Pedidos - Painel Administrativo</title>
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
                <h1 class="text-2xl font-semibold text-gray-700">Pedidos Recebidos</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php" class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Pedido ID</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Cliente</th>
                                <th class="p-3 text-left text-sm font-semibold text-gray-600 uppercase">Data</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Valor Total</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Status</th>
                                <th class="p-3 text-center text-sm font-semibold text-gray-600 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td class="p-3 text-sm text-gray-700 font-bold">#<?php echo $pedido['id']; ?></td>
                                    <td class="p-3 text-sm text-gray-700"><?php echo htmlspecialchars($pedido['nome'] . ' ' . $pedido['sobrenome']); ?></td>
                                    <td class="p-3 text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                    <td class="p-3 text-sm text-center font-semibold text-green-600">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                    <td class="p-3 text-center text-sm">
                                        <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo getStatusClass($pedido['status']); ?>">
                                            <?php echo ucfirst($pedido['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="details/pedido_detalhes.php?id=<?php echo $pedido['id']; ?>" class="text-blue-500 hover:text-blue-700" title="Ver Detalhes">
                                            <i class="fas fa-eye"></i> Ver Detalhes
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pedidos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center p-4 text-gray-500">Nenhum pedido encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>

</html>