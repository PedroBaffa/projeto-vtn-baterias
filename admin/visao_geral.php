<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username']);
require_once 'config.php';

// --- BUSCAR AS ESTATÍSTICAS E PRÉ-VISUALIZAÇÕES ---

// 1. Total de Produtos
$stmt_total_produtos = $conn->query("SELECT id, title, sku FROM produtos ORDER BY id DESC LIMIT 3");
$produtos_recentes_preview = $stmt_total_produtos->fetchAll(PDO::FETCH_ASSOC);
$total_produtos = $conn->query("SELECT COUNT(*) FROM produtos")->fetchColumn();

// 2. Contatos (Leads)
$stmt_contatos = $conn->query("SELECT id, nome, whatsapp FROM contatos ORDER BY data_criacao DESC LIMIT 3");
$contatos_preview = $stmt_contatos->fetchAll(PDO::FETCH_ASSOC);
$total_contatos = $conn->query("SELECT COUNT(*) FROM contatos")->fetchColumn();

// 3. Total de Admins
$stmt_admins = $conn->query("SELECT id, usuario FROM administradores ORDER BY id DESC LIMIT 3");
$admins_preview = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
$total_admins = $conn->query("SELECT COUNT(*) FROM administradores")->fetchColumn();

// 4. PROMOÇÕES ATIVAS
$stmt_promocoes = $conn->query("SELECT id, nome FROM promocoes WHERE is_active = 1 AND NOW() BETWEEN data_inicio AND data_fim ORDER BY data_criacao DESC LIMIT 3");
$promocoes_ativas_preview = $stmt_promocoes->fetchAll(PDO::FETCH_ASSOC);
$total_promocoes_ativas = $conn->query("SELECT COUNT(*) FROM promocoes WHERE is_active = 1 AND NOW() BETWEEN data_inicio AND data_fim")->fetchColumn();

// 5. [NOVO] DADOS DE PEDIDOS
$stmt_pedidos = $conn->query("SELECT p.id, p.status, u.nome, u.sobrenome FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.data_pedido DESC LIMIT 3");
$pedidos_recentes_preview = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
$total_pedidos = $conn->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$pedidos_novos = $conn->query("SELECT COUNT(*) FROM pedidos WHERE status = 'novo' OR status = 'chamou'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Visão Geral - Painel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        
        <?php require_once 'templates/sidebar.php'; // Caminho para o novo menu lateral ?>

        <div class="flex-1 flex flex-col">
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <h1 class="text-2xl font-semibold text-gray-700">Visão Geral</h1>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Olá, <?php echo $username; ?>!</span>
                    <a href="logout.php"
                        class="text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Sair</a>
                </div>
            </header>
            <main class="flex-1 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Total de Pedidos</h3>
                            <span class="text-2xl font-bold text-orange-500"><?php echo $total_pedidos; ?></span>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-gray-400 font-semibold uppercase flex items-center">
                                Pedidos Recentes
                                <?php if ($pedidos_novos > 0): ?>
                                    <span class='ml-2 text-xs font-bold text-white bg-red-500 rounded-full px-2 py-1'><?php echo $pedidos_novos; ?> Novo(s)</span>
                                <?php endif; ?>
                            </p>
                            <?php if (empty($pedidos_recentes_preview)): ?>
                                <p class="text-sm text-gray-500 italic">Nenhum pedido recebido ainda.</p>
                            <?php else: ?>
                                <?php foreach ($pedidos_recentes_preview as $pedido): ?>
                                    <a href="details/pedido_detalhes.php?id=<?php echo $pedido['id']; ?>" class="block hover:bg-gray-50 p-2 rounded-md">
                                        <div class="flex justify-between items-center">
                                            <p class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($pedido['nome'] . ' ' . $pedido['sobrenome']); ?></p>
                                            <span class="text-xs font-semibold text-gray-500"><?php echo ucfirst($pedido['status']); ?></span>
                                        </div>
                                        <p class="text-xs text-gray-400">Pedido #<?php echo $pedido['id']; ?></p>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="pedidos.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver Todos &rarr;</a>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Total de Produtos</h3>
                            <span class="text-2xl font-bold text-blue-500"><?php echo $total_produtos; ?></span>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-gray-400 font-semibold uppercase">Últimos Adicionados</p>
                            <?php foreach ($produtos_recentes_preview as $produto): ?>
                                <a href="forms/form_produto.php?acao=editar&id=<?php echo $produto['id']; ?>" class="block hover:bg-gray-50 p-2 rounded-md">
                                    <p class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($produto['title']); ?></p>
                                    <p class="text-xs text-gray-400">SKU: <?php echo htmlspecialchars($produto['sku']); ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="dashboard.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver Todos &rarr;</a>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Promoções Ativas</h3>
                            <span class="text-2xl font-bold text-green-500"><?php echo $total_promocoes_ativas; ?></span>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-gray-400 font-semibold uppercase">Promoções em Vigor</p>
                            <?php if (empty($promocoes_ativas_preview)): ?>
                                <p class="text-sm text-gray-500 italic">Nenhuma promoção ativa no momento.</p>
                            <?php else: ?>
                                <?php foreach ($promocoes_ativas_preview as $promo): ?>
                                    <a href="forms/form_promocao.php?id=<?php echo $promo['id']; ?>" class="block hover:bg-gray-50 p-2 rounded-md">
                                        <p class="font-medium text-gray-800 truncate"><i class="fas fa-tags mr-2 text-gray-400"></i><?php echo htmlspecialchars($promo['nome']); ?></p>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="promocoes.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver Todas &rarr;</a>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Contatos (Leads)</h3>
                            <span class="text-2xl font-bold text-purple-500"><?php echo $total_contatos; ?></span>
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-gray-400 font-semibold uppercase">Últimos Registrados</p>
                            <?php if (empty($contatos_preview)): ?>
                                <p class="text-sm text-gray-500 italic">Nenhum contato ainda.</p>
                            <?php else: ?>
                                <?php foreach ($contatos_preview as $contato): ?>
                                    <div class="block hover:bg-gray-50 p-2 rounded-md">
                                        <p class="font-medium text-gray-800 truncate"><i class="fas fa-user mr-2 text-gray-400"></i><?php echo htmlspecialchars($contato['nome']); ?></p>
                                        <p class="text-xs text-gray-400 ml-6"><?php echo htmlspecialchars($contato['whatsapp']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="contatos.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver Todos &rarr;</a>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>

</html>